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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Flash Manager Ultimate</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f1f5f9; }
        .g-card { background: rgba(255, 255, 255, 0.95); border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.05); }
        .input-box { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; outline: none; margin-top: 5px; font-size: 0.9rem; background: #fdfdfe; transition: all 0.2s; }
        .input-box:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); background: #fff; }
        
        .preview-phone { width: 100%; max-width: 320px; margin: 0 auto; background: #fff; border-radius: 35px; border: 8px solid #1e293b; overflow: hidden; position: relative; height: 450px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); }
        .p-screen { padding: 15px; height: 100%; background: #f8fafc; display: flex; flex-direction: column; justify-content: center; }
        .p-deal-card { background: white; border-radius: 18px; padding: 15px; text-align: center; box-shadow: 0 10px 30px -5px rgba(79, 70, 229, 0.15); border: 1px solid #eef2ff; }
        .p-badge { background: #fee2e2; color: #ef4444; font-size: 9px; padding: 4px 8px; border-radius: 50px; text-transform: uppercase; font-weight: 800; display: inline-block; margin-bottom: 8px; }
        .p-title { font-size: 13px; font-weight: 800; color: #1e293b; line-height: 1.3; margin-bottom: 10px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; min-height: 34px; }
        .p-price { font-size: 24px; font-weight: 900; color: #16a34a; display: block; }
        .p-old { font-size: 12px; text-decoration: line-through; color: #94a3b8; font-weight: 600; }
        .p-btn { background: #1e293b; color: white; border-radius: 12px; padding: 10px; font-size: 12px; font-weight: 700; width: 100%; display: block; margin-top: 10px; }
    </style>
</head>
<body class="p-4 md:p-6 text-slate-800">

<div class="max-w-7xl mx-auto">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div class="text-center md:text-left">
            <h1 class="text-3xl font-extrabold text-slate-800">⚡ Flash Manager <span class="text-indigo-600">v14</span></h1>
            <p class="text-slate-500 font-medium text-sm">Clean Text • Live Rates • Full Control</p>
        </div>
        <div class="grid grid-cols-2 gap-3 w-full md:w-auto">
            <div class="g-card px-5 py-3 text-center bg-white">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Revenue</span>
                <span class="text-xl font-black text-green-600">Rs <?= number_format($stats['rev']) ?></span>
            </div>
            <div class="g-card px-5 py-3 text-center bg-white">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Orders</span>
                <span class="text-xl font-black text-indigo-600"><?= number_format($stats['sold']) ?></span>
            </div>
        </div>
    </div>

    <?php if($success_msg): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-3 rounded-r-lg mb-6 shadow-sm flex items-center gap-2 text-sm font-semibold">
            <i class="fa-solid fa-check-circle text-lg"></i> <div><?= $success_msg ?></div>
        </div>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-800 p-3 rounded-r-lg mb-6 shadow-sm flex items-center gap-2 text-sm font-semibold">
            <i class="fa-solid fa-triangle-exclamation text-lg"></i> <div><?= $error_msg ?></div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <div class="lg:col-span-8 space-y-6">
            
            <div class="g-card p-6 relative">
                <div class="flex items-center gap-3 mb-5 border-b border-slate-100 pb-3">
                    <div class="bg-indigo-600 text-white w-9 h-9 rounded-lg flex items-center justify-center shadow-lg shadow-indigo-200">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <h3 class="font-bold text-lg text-slate-800">Create Deal</h3>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="set_by_selection" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1 block">Category</label>
                            <select id="catSelect" onchange="loadServices()" class="input-box cursor-pointer">
                                <option value="">-- Choose Category --</option>
                                <?php foreach($cats as $c) echo "<option value='".htmlspecialchars(clean_text($c))."'>".clean_text($c)."</option>"; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1 block">Service (With Rates)</label>
                            <select name="selected_item_id" id="itemSelect" class="input-box disabled:bg-slate-50 disabled:text-slate-400 cursor-pointer" onchange="updatePreview()" disabled>
                                <option value="">-- Choose Category First --</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1 block"><i class="fa-regular fa-clock"></i> Duration</label>
                            <select name="duration" class="input-box cursor-pointer">
                                <?php for($i=1; $i<=24; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($i==24)?'selected':'' ?>><?= $i ?> Hour<?= ($i>1)?'s':'' ?></option>
                                <?php endfor; ?>
                                <option value="48">48 Hours</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1 block"><i class="fa-solid fa-percent"></i> Discount %</label>
                            <input type="number" name="discount_percent" id="discInput" class="input-box" placeholder="e.g. 30" min="1" max="90" oninput="updatePreview()">
                        </div>
                    </div>

                    <div id="profitBox" class="hidden mb-5 bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex justify-between items-center">
                        <div>
                            <span class="text-[10px] font-bold text-emerald-600 uppercase">Net Profit / 1k</span>
                            <div class="text-emerald-800 font-bold text-lg" id="profitVal">Rs 0.00</div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold text-emerald-600 uppercase">Provider Cost</span>
                            <div class="text-slate-600 font-bold text-sm" id="costVal">Rs 0.00</div>
                        </div>
                    </div>

                    <button type="submit" id="btnActivate" class="w-full bg-slate-900 text-white font-bold py-3.5 rounded-xl hover:bg-slate-800 transition flex justify-center items-center gap-2 opacity-50 cursor-not-allowed shadow-lg" disabled>
                        Launch Deal Now <i class="fa-solid fa-rocket text-yellow-400"></i>
                    </button>
                </form>
            </div>

            <div class="g-card p-5 bg-gradient-to-r from-blue-600 to-indigo-700 text-white shadow-lg shadow-indigo-200">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-center md:text-left">
                        <h4 class="font-bold text-base"><i class="fa-solid fa-robot mr-2"></i> Auto-Shuffle</h4>
                        <p class="text-xs text-blue-100 opacity-80">Cycle any random profitable service.</p>
                    </div>
                    <form method="POST" class="flex gap-2 items-center">
                        <select name="duration" class="bg-white/20 border border-white/30 text-white text-xs rounded-lg p-2 outline-none cursor-pointer">
                            <?php for($i=1; $i<=24; $i++): ?>
                                <option value="<?= $i ?>" class="text-slate-800" <?= ($i==24)?'selected':'' ?>><?= $i ?>H</option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" name="switch_random" class="bg-white text-indigo-700 font-bold py-2 px-6 rounded-lg hover:bg-blue-50 transition shadow-lg flex justify-center items-center gap-2">
                            <i class="fa-solid fa-shuffle"></i> Go
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <div class="lg:col-span-4 space-y-6">
            
            <div>
                <h4 class="text-xs font-bold text-slate-400 uppercase mb-3 text-center tracking-widest">Live Mobile View</h4>
                <div class="preview-phone">
                    <div class="p-screen">
                        <div class="text-center mb-4 opacity-30 text-[10px]">
                            <i class="fa-solid fa-signal"></i> <span>SubHub</span> <i class="fa-solid fa-battery-full"></i>
                        </div>
                        
                        <div class="p-deal-card">
                            <div class="p-badge">⚡ Flash Deal</div>
                            <div class="p-title" id="pTitle">Select a service...</div>
                            <div class="p-price-box">
                                <span class="p-old" id="pOld">Rs ---</span><br>
                                <span class="p-price" id="pPrice">Rs 0.00</span>
                            </div>
                            <button class="p-btn">Claim Deal <i class="fa-solid fa-arrow-right ml-1"></i></button>
                        </div>

                        <div class="mt-4 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Applied Discount</p>
                            <span class="text-2xl font-black text-slate-800" id="pDisc">0%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="g-card p-4 border-l-4 border-green-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Currently Active</p>
                        <h4 class="font-bold text-sm text-slate-800 truncate w-32 md:w-40">
                            <?= clean_text($active['item_name'] ?? 'None') ?>
                        </h4>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Ends In</p>
                        <span class="font-mono font-bold text-indigo-600 text-sm">
                            <?= $active ? date("H:i", strtotime($active['end_time'])) : '--:--' ?>
                        </span>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <div class="g-card mt-8 overflow-hidden">
        <div class="bg-slate-50 px-5 py-3 border-b border-slate-200 font-bold text-slate-700 text-sm">
            <i class="fa-solid fa-list mr-2 text-indigo-500"></i> Recent SMM Orders
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Service</th>
                        <th class="px-4 py-3">Paid</th>
                        <th class="px-4 py-3 text-right">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs md:text-sm">
                    <?php foreach($logs as $log): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-bold"><?= clean_text($log['u_name']) ?></td>
                        <td class="px-4 py-3 text-slate-600 truncate max-w-[120px]">
                            <?= clean_text($log['item_name']) ?>
                        </td>
                        <td class="px-4 py-3 font-bold text-green-600">Rs <?= (float)$log['amount_paid'] ?></td>
                        <td class="px-4 py-3 text-right text-slate-400 font-mono"><?= date("d M, H:i", strtotime($log['created_at'])) ?></td>
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
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        
        if (cat && smmData[cat]) {
            itemSelect.disabled = false;
            smmData[cat].forEach(svc => {
                let opt = document.createElement('option');
                opt.value = svc.id;
                // Clean text for options is handled in PHP, here we just display
                opt.innerText = `[${svc.id}] ${svc.name} (Cost: ${svc.cost} | Sell: ${svc.price})`;
                
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
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            profitBox.classList.remove('hidden');
            
            const opt = select.options[select.selectedIndex];
            const name = opt.getAttribute('data-name');
            const sellPrice = parseFloat(opt.getAttribute('data-price'));
            const costPrice = parseFloat(opt.getAttribute('data-cost'));
            
            let discPercent = parseFloat(discInput.value);
            if(isNaN(discPercent) || discPercent < 1) discPercent = 20; 
            
            let newPrice = sellPrice - (sellPrice * (discPercent / 100));
            
            if(newPrice < (costPrice * 1.05)) {
               // Safety visual only
            }

            let profit = newPrice - costPrice;

            document.getElementById('pTitle').innerText = name;
            document.getElementById('pOld').innerText = 'Rs ' + sellPrice.toFixed(2);
            document.getElementById('pPrice').innerText = 'Rs ' + newPrice.toFixed(2);
            document.getElementById('pDisc').innerText = '-' + discPercent + '%';

            document.getElementById('profitVal').innerText = 'Rs ' + profit.toFixed(2);
            document.getElementById('costVal').innerText = 'Rs ' + costPrice.toFixed(2);
            
            if(profit > 0) {
                document.getElementById('profitVal').className = "text-emerald-800 font-bold text-lg";
            } else {
                document.getElementById('profitVal').className = "text-red-600 font-bold text-lg";
            }

        } else {
            // Reset
            document.getElementById('pTitle').innerText = 'Select a service...';
            document.getElementById('pPrice').innerText = 'Rs 0.00';
            document.getElementById('pOld').innerText = 'Rs ---';
            document.getElementById('pDisc').innerText = '0%';
            profitBox.classList.add('hidden');
        }
    }
</script>

</body>
</html>