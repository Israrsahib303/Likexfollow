<?php
// panel/flash_manage.php
// Beast9 - Flash Manager (v14.0 - Text Cleaner + Encoding Fix)
session_start();
require_once '../includes/db.php';

// --- AUTH CHECK ---
if (!isset($_SESSION['admin_logged_in'])) {
    // header("Location: login.php"); exit; 
}

// --- HELPER: FORCE CLEAN TEXT (Removes &amp; etc) ---
function clean_text($str) {
    if (!$str) return '';
    // Decode twice to handle double encoded strings like &amp;amp;
    $s = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// =========================================================
// 🔄 HANDLE: ACTIVATION LOGIC
// =========================================================
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $manual_item = null;
    $duration_hours = (int)($_POST['duration'] ?? 24); 
    $custom_discount = (int)($_POST['discount_percent'] ?? 0); 

    // --- SAVE DURATION PREFERENCE ---
    try {
        $check = $db->query("SELECT setting_value FROM settings WHERE setting_key='flash_deal_cycle'")->fetch();
        if($check) {
            $db->prepare("UPDATE settings SET setting_value=? WHERE setting_key='flash_deal_cycle'")->execute([$duration_hours]);
        } else {
            $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('flash_deal_cycle', ?)")->execute([$duration_hours]);
        }
    } catch(Exception $e) {}

    // 1. Set by Provider Service ID
    if (isset($_POST['set_by_provider_id'])) {
        $pid = trim($_POST['provider_service_id']);
        $stmt = $db->prepare("SELECT * FROM smm_services WHERE (service_id = ? OR id = ?) AND is_active = 1 LIMIT 1");
        $stmt->execute([$pid, $pid]);
        $manual_item = $stmt->fetch();
        
        if (!$manual_item) $error_msg = "⚠️ Service not found with ID: $pid";
    }

    // 2. Set by Dropdown Selection
    elseif (isset($_POST['set_by_selection'])) {
        $item_id = (int)$_POST['selected_item_id'];
        $manual_item = $db->query("SELECT * FROM smm_services WHERE id = $item_id AND is_active = 1")->fetch();
        if (!$manual_item) $error_msg = "⚠️ Please select a service first.";
    }

    // 3. Auto Shuffle (ALL PROFITABLE SERVICES)
    elseif (isset($_POST['switch_random'])) {
        $all_services = $db->query("SELECT * FROM smm_services WHERE is_active=1")->fetchAll();
        $candidates = [];
        
        foreach($all_services as $s) {
            // Safety: Selling price must be > Cost + 5%
            if ($s['service_rate'] > ($s['base_price'] * 1.05)) { 
                $candidates[] = $s;
            }
        }

        if (!empty($candidates)) {
            $manual_item = $candidates[array_rand($candidates)];
            $custom_discount = rand(15, 35); 
        } else {
            $error_msg = "⚠️ No profitable services found for shuffle.";
        }
    }

    // --- APPLY DEAL ---
    if ($manual_item) {
        // Expire Current
        $db->query("UPDATE flash_sales SET status='expired' WHERE status='active'");
        
        // 🔥 FIX: Clean Name Before Inserting into Database
        $name = clean_text($manual_item['name']); 
        
        $old_price = (float)$manual_item['service_rate'];
        $cost = (float)$manual_item['base_price'];
        
        // Discount Logic
        $final_discount = ($custom_discount > 0) ? $custom_discount : 20;
        $new_price = $old_price - ($old_price * ($final_discount / 100));
        
        // Profit Safety
        if ($new_price < ($cost * 1.05)) {
            $new_price = $cost * 1.05; 
        }

        // Time Logic
        $start_time = date('Y-m-d H:i:s');
        $end_time = date('Y-m-d H:i:s', strtotime("+$duration_hours hours"));

        // Insert
        $stmt = $db->prepare("INSERT INTO flash_sales (type, item_id, item_name, original_price, discounted_price, start_time, end_time, status, max_claims) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)");
        $stmt->execute(['smm', $manual_item['id'], $name, $old_price, $new_price, $start_time, $end_time, rand(50, 200)]);
        
        $_SESSION['flash_msg'] = "🔥 Active: <b>" . htmlspecialchars($name) . "</b> ({$duration_hours}H)";
        
        header("Location: flash_manage.php");
        exit;
    }
}

if (isset($_SESSION['flash_msg'])) {
    $success_msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// =========================================================
// 📊 FETCH DATA
// =========================================================
$active = $db->query("SELECT * FROM flash_sales WHERE status='active' AND end_time > NOW() LIMIT 1")->fetch();
$stats = $db->query("SELECT COUNT(id) as sold, COALESCE(SUM(amount_paid), 0) as rev FROM flash_orders")->fetch();

$cats = $db->query("SELECT DISTINCT category FROM smm_services WHERE is_active=1 ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
$all_services = $db->query("SELECT id, name, category, service_rate, base_price FROM smm_services WHERE is_active=1")->fetchAll();

$svc_grouped = [];
foreach($all_services as $s) {
    // 🔥 FIX: Clean names for Dropdown/JS
    $c_cat = clean_text($s['category']);
    $c_name = clean_text($s['name']);
    
    $svc_grouped[$c_cat][] = [
        'id' => $s['id'], 
        'name' => $c_name,
        'price' => (float)$s['service_rate'],
        'cost' => (float)$s['base_price']
    ];
}

$logs = $db->query("
    SELECT o.*, u.name as u_name, f.item_name 
    FROM flash_orders o 
    JOIN users u ON o.user_id=u.id 
    JOIN flash_sales f ON o.flash_id=f.id 
    ORDER BY o.id DESC LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flash Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=San+Francisco+Pro+Display:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --ios-bg: #F5F5F7;
            --ios-card: #FFFFFF;
            --ios-text: #1D1D1F;
            --ios-text-sec: #86868B;
            --ios-blue: #0071E3;
            --ios-green: #34C759;
            --ios-red: #FF3B30;
            --ios-indigo: #5856D6;
            --ios-border: #E5E5EA;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 12px 30px rgba(0,0,0,0.12);
            --radius: 20px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Inter", sans-serif;
            background-color: var(--ios-bg);
            color: var(--ios-text);
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .ios-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Header & Stats */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .page-title p {
            margin: 6px 0 0;
            color: var(--ios-text-sec);
            font-size: 15px;
        }

        .stats-group {
            display: flex;
            gap: 16px;
        }

        .stat-card {
            background: var(--ios-card);
            border-radius: 16px;
            padding: 16px 24px;
            box-shadow: var(--shadow-sm);
            text-align: center;
            min-width: 120px;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--ios-text-sec); letter-spacing: 0.5px; display: block; margin-bottom: 4px; }
        .stat-val { font-size: 20px; font-weight: 700; color: var(--ios-text); }
        .stat-val.green { color: var(--ios-green); }
        .stat-val.indigo { color: var(--ios-indigo); }

        /* Main Grid */
        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        /* Card Styles */
        .ios-card {
            background: var(--ios-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            padding: 30px;
            border: 1px solid rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--ios-border);
        }
        .icon-box {
            width: 36px; height: 36px;
            background: var(--ios-blue);
            color: white;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(0, 113, 227, 0.3);
        }
        .card-title { font-size: 18px; font-weight: 700; margin: 0; }

        /* Form Elements */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 0; }
        .label { display: block; font-size: 12px; font-weight: 600; color: var(--ios-text-sec); margin-bottom: 8px; text-transform: uppercase; }
        
        .ios-select, .ios-input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid transparent;
            background: #F2F2F7;
            font-size: 15px;
            font-family: inherit;
            color: var(--ios-text);
            transition: all 0.2s;
            box-sizing: border-box;
            appearance: none;
        }
        .ios-select {
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2386868B%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 15px top 50%;
            background-size: 10px auto;
            cursor: pointer;
        }
        .ios-input:focus, .ios-select:focus {
            background: #fff;
            border-color: var(--ios-blue);
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.1);
            outline: none;
        }
        .ios-input:disabled, .ios-select:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Profit Box */
        .profit-box {
            background: #F0FDF4;
            border: 1px solid #DCFCE7;
            border-radius: 14px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            opacity: 0; 
            transform: translateY(10px);
            transition: all 0.3s;
            display: none;
        }
        .profit-box.visible { opacity: 1; transform: translateY(0); display: flex; }
        
        .profit-val { font-size: 20px; font-weight: 700; color: #166534; }
        .cost-val { font-size: 14px; color: var(--ios-text-sec); font-weight: 600; }

        /* Main Button */
        .btn-primary {
            width: 100%;
            background: var(--ios-text);
            color: #fff;
            padding: 16px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .btn-primary:hover:not(:disabled) { transform: scale(1.01); background: #000; box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Auto Shuffle Card */
        .shuffle-card {
            background: linear-gradient(135deg, var(--ios-blue), #5AC8FA);
            color: white;
            padding: 24px;
            border-radius: var(--radius);
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 113, 227, 0.25);
        }
        .shuffle-info h4 { margin: 0 0 4px; font-size: 16px; font-weight: 700; }
        .shuffle-info p { margin: 0; font-size: 13px; opacity: 0.9; }
        
        .shuffle-controls { display: flex; gap: 10px; }
        .shuffle-select { 
            background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; 
            padding: 8px 12px; border-radius: 10px; font-size: 13px; outline: none; cursor: pointer;
        }
        .shuffle-select option { color: #000; }
        .btn-shuffle {
            background: white; color: var(--ios-blue); border: none; padding: 8px 20px;
            border-radius: 10px; font-weight: 700; font-size: 13px; cursor: pointer;
            transition: 0.2s;
        }
        .btn-shuffle:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

        /* Phone Preview */
        .phone-bezel {
            background: #fff;
            border: 8px solid #2c2c2e;
            border-radius: 40px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            height: 520px;
            max-width: 300px;
            margin: 0 auto;
            position: relative;
        }
        .phone-screen {
            background: #F5F5F7;
            height: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .preview-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }
        .p-badge { background: #FF3B30; color: white; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; display: inline-block; margin-bottom: 12px; }
        .p-title { font-size: 14px; font-weight: 700; color: #1D1D1F; line-height: 1.4; margin-bottom: 12px; min-height: 40px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .p-old { font-size: 13px; text-decoration: line-through; color: #86868B; }
        .p-new { font-size: 26px; font-weight: 800; color: #34C759; margin: 4px 0 16px; display: block; }
        .p-cta { background: #1D1D1F; color: white; width: 100%; padding: 12px; border-radius: 12px; font-size: 13px; font-weight: 600; border: none; }
        
        .active-status-card {
            background: #fff;
            border-left: 4px solid var(--ios-green);
            padding: 16px;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Recent Orders Table */
        .table-section h3 { margin: 0 0 20px; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .table-responsive { overflow-x: auto; }
        .ios-table { width: 100%; border-collapse: collapse; min-width: 600px; }
        .ios-table th { text-align: left; padding: 14px 20px; color: var(--ios-text-sec); font-size: 11px; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid var(--ios-border); background: #FAFAFA; }
        .ios-table td { padding: 16px 20px; border-bottom: 1px solid var(--ios-border); font-size: 14px; }
        .ios-table tr:hover td { background: #F9F9F9; }
        
        /* Alerts */
        .alert-box { padding: 14px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
        .alert-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }

        @media (max-width: 900px) {
            .main-grid { grid-template-columns: 1fr; }
            .header-section { flex-direction: column; align-items: flex-start; }
            .stats-group { width: 100%; }
            .stat-card { flex: 1; }
        }
    </style>
</head>
<body>

<div class="ios-container">
    
    <div class="header-section">
        <div class="page-title">
            <h1>Flash Manager</h1>
            <p>Clean Text • Live Rates • Full Control</p>
        </div>
        <div class="stats-group">
            <div class="stat-card">
                <span class="stat-label">Total Revenue</span>
                <span class="stat-val green">Rs <?= number_format($stats['rev']) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Orders</span>
                <span class="stat-val indigo"><?= number_format($stats['sold']) ?></span>
            </div>
        </div>
    </div>

    <?php if($success_msg): ?>
        <div class="alert-box alert-success"><i class="fa-solid fa-check-circle"></i> <?= $success_msg ?></div>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <div class="alert-box alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error_msg ?></div>
    <?php endif; ?>

    <div class="main-grid">
        
        <div>
            <div class="ios-card">
                <div class="card-header">
                    <div class="icon-box"><i class="fa-solid fa-sliders"></i></div>
                    <h3 class="card-title">Create Deal</h3>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="set_by_selection" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="label">Category</label>
                            <select id="catSelect" onchange="loadServices()" class="ios-select">
                                <option value="">-- Select --</option>
                                <?php foreach($cats as $c) echo "<option value='".htmlspecialchars(clean_text($c))."'>".clean_text($c)."</option>"; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="label">Service</label>
                            <select name="selected_item_id" id="itemSelect" class="ios-select" onchange="updatePreview()" disabled>
                                <option value="">-- Choose Category First --</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="label">Duration</label>
                            <select name="duration" class="ios-select">
                                <?php for($i=1; $i<=24; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($i==24)?'selected':'' ?>><?= $i ?> Hour<?= ($i>1)?'s':'' ?></option>
                                <?php endfor; ?>
                                <option value="48">48 Hours</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="label">Discount %</label>
                            <input type="number" name="discount_percent" id="discInput" class="ios-input" placeholder="e.g. 30" min="1" max="90" oninput="updatePreview()">
                        </div>
                    </div>

                    <div id="profitBox" class="profit-box">
                        <div>
                            <span class="label">Net Profit / 1k</span>
                            <div class="profit-val" id="profitVal">Rs 0.00</div>
                        </div>
                        <div style="text-align: right;">
                            <span class="label">Provider Cost</span>
                            <div class="cost-val" id="costVal">Rs 0.00</div>
                        </div>
                    </div>

                    <button type="submit" id="btnActivate" class="btn-primary" disabled>
                        Launch Deal <i class="fa-solid fa-rocket"></i>
                    </button>
                </form>
            </div>

            <div class="shuffle-card">
                <div class="shuffle-info">
                    <h4><i class="fa-solid fa-robot"></i> Auto-Shuffle</h4>
                    <p>Cycle a random profitable service.</p>
                </div>
                <form method="POST" class="shuffle-controls">
                    <select name="duration" class="shuffle-select">
                        <?php for($i=1; $i<=24; $i++): ?>
                            <option value="<?= $i ?>" <?= ($i==24)?'selected':'' ?>><?= $i ?>H</option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" name="switch_random" class="btn-shuffle">Go</button>
                </form>
            </div>
        </div>

        <div>
            <div style="text-align:center; margin-bottom:15px;">
                <span class="label">Live Mobile View</span>
            </div>
            
            <div class="phone-bezel">
                <div class="phone-screen">
                    <div style="text-align:center; opacity:0.3; margin-bottom:20px; font-size:10px;">
                        <i class="fa-solid fa-signal"></i> 5G <i class="fa-solid fa-battery-full ml-2"></i>
                    </div>
                    
                    <div class="preview-card">
                        <span class="p-badge">⚡ Flash Deal</span>
                        <div class="p-title" id="pTitle">Select a service...</div>
                        <div class="p-old" id="pOld">Rs ---</div>
                        <div class="p-new" id="pPrice">Rs 0.00</div>
                        <button class="p-cta">Claim Now <i class="fa-solid fa-arrow-right"></i></button>
                    </div>

                    <div style="text-align:center; margin-top:20px;">
                        <span class="label">Discount</span>
                        <div style="font-size:24px; font-weight:800; color:#1D1D1F;" id="pDisc">0%</div>
                    </div>
                </div>
            </div>

            <div class="active-status-card">
                <div>
                    <span class="label">Active Now</span>
                    <div style="font-weight:700; font-size:14px; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= clean_text($active['item_name'] ?? 'None') ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <span class="label">Ends In</span>
                    <div style="font-weight:700; font-family:'SF Mono', monospace; color:var(--ios-blue);">
                        <?= $active ? date("H:i", strtotime($active['end_time'])) : '--:--' ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="ios-card table-section">
        <h3><i class="fa-solid fa-list text-blue-500"></i> Recent SMM Orders</h3>
        <div class="table-responsive">
            <table class="ios-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Service</th>
                        <th>Paid</th>
                        <th style="text-align:right;">Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $log): ?>
                    <tr>
                        <td style="font-weight:600;"><?= clean_text($log['u_name']) ?></td>
                        <td>
                            <div style="max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:var(--ios-text-sec);">
                                <?= clean_text($log['item_name']) ?>
                            </div>
                        </td>
                        <td style="color:var(--ios-green); font-weight:700;">Rs <?= (float)$log['amount_paid'] ?></td>
                        <td style="text-align:right; font-family:'SF Mono', monospace; color:var(--ios-text-sec);"><?= date("d M, H:i", strtotime($log['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    const smmData = <?= json_encode($svc_grouped) ?>;

    function loadServices() {
        const cat = document.getElementById('catSelect').value;
        const itemSelect = document.getElementById('itemSelect');
        const btn = document.getElementById('btnActivate');
        
        itemSelect.innerHTML = '<option value="">-- Select Service --</option>';
        itemSelect.disabled = true;
        btn.disabled = true;
        
        if (cat && smmData[cat]) {
            itemSelect.disabled = false;
            smmData[cat].forEach(svc => {
                let opt = document.createElement('option');
                opt.value = svc.id;
                opt.innerText = `[${svc.id}] ${svc.name}`;
                
                opt.setAttribute('data-name', svc.name);
                opt.setAttribute('data-price', svc.price);
                opt.setAttribute('data-cost', svc.cost);
                
                itemSelect.appendChild(opt);
            });
        }
        updatePreview();
    }

    function updatePreview() {
        const select = document.getElementById('itemSelect');
        const btn = document.getElementById('btnActivate');
        const profitBox = document.getElementById('profitBox');
        const discInput = document.getElementById('discInput');
        
        if (select.value) {
            btn.disabled = false;
            profitBox.classList.add('visible');
            
            const opt = select.options[select.selectedIndex];
            const name = opt.getAttribute('data-name');
            const sellPrice = parseFloat(opt.getAttribute('data-price'));
            const costPrice = parseFloat(opt.getAttribute('data-cost'));
            
            let discPercent = parseFloat(discInput.value);
            if(isNaN(discPercent) || discPercent < 1) discPercent = 20; 
            
            let newPrice = sellPrice - (sellPrice * (discPercent / 100));
            
            if(newPrice < (costPrice * 1.05)) {
               // Logic handled in PHP, visual cue only
            }

            let profit = newPrice - costPrice;

            document.getElementById('pTitle').innerText = name;
            document.getElementById('pOld').innerText = 'Rs ' + sellPrice.toFixed(2);
            document.getElementById('pPrice').innerText = 'Rs ' + newPrice.toFixed(2);
            document.getElementById('pDisc').innerText = '-' + discPercent + '%';

            document.getElementById('profitVal').innerText = 'Rs ' + profit.toFixed(2);
            document.getElementById('costVal').innerText = 'Rs ' + costPrice.toFixed(2);
            
            if(profit > 0) {
                document.getElementById('profitVal').style.color = "#166534";
            } else {
                document.getElementById('profitVal').style.color = "#DC2626";
            }

        } else {
            // Reset
            document.getElementById('pTitle').innerText = 'Select a service...';
            document.getElementById('pPrice').innerText = 'Rs 0.00';
            document.getElementById('pOld').innerText = 'Rs ---';
            document.getElementById('pDisc').innerText = '0%';
            profitBox.classList.remove('visible');
        }
    }
</script>

</body>
</html>
